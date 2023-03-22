<?php 
GFForms::include_addon_framework();
class GFSendinblueAddOn extends GFAddOn {
	protected $_version = GF_SENDINBLUE_ADDON_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug = 'gf-sendinblue';
	protected $_path = 'sendinblue-add-on-gf/sendinblue-add-on-gf.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Add on for Sendinblue on Gravity Forms';
	protected $_short_title = 'Sendinblue Add On';
	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFSendinblueAddOn();
		}
		return self::$_instance;
	}

	public function init() {
		parent::init();
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
	}
	
	public function form_settings_fields( $form ) {
		$email_fields = array();
		$name_fields  = array( array('label'=>'select field','value'=>'') );
		$list_fields  = array(array('label'=>'select list','value'=>''));

		foreach ( $form['fields'] as &$field ) {
			if($field->type == 'name'){
				$name = $field['inputs'];
				foreach ($name as $key => $input) {
				    if( $input['label']=='Prefix' || $input['label']=='Suffix'  ) continue;
					$name_fields[] = array('label'=>$field->label.' ('.$input['label'].')','value'=>$input['id']);
				}
			}elseif($field->type == 'email'){
				$email_fields[] = array('label'=>$field->label,'value'=>$field->id);
			}elseif($field->type == 'text'){
				$name_fields[] = array('label'=>$field->label,'value'=>$field->id);
			}elseif($field->type == 'checkbox'){
                $name_fields[] = array('label'=>$field->label,'value'=>$field->id);
            }
 		}

        if( !empty($form['gf-sendinblue']['key']) ){
    		$url = 'https://api.sendinblue.com/v3/contacts/lists';
    		$response = wp_remote_get( $url, array(
    			'headers' => array(
    				'Accept' => 'application/json',
    				'api-key' => $form['gf-sendinblue']['key'],
    			),
    		));
    		$resbody = json_decode($response['body'],true);
    		$list_field = array();
    		if( !empty($resbody) ){
    			foreach( $resbody['lists'] as $d ){ 
    				$list_fields[] = array('label'=>$d['name'],'value'=>$d['id']);
    			}
    		}
        }
        $list_field = array(
			'label'   => esc_html__( 'Contact List', 'gf-sendinblue' ),
			'type'    => 'select',
			'name'    => 'list',
			'choices' => $list_fields,
			'tooltip' => esc_html__( 'Contact List Only Visible After Add Correct Sendinblue API Key' )
		);
        
        // Fetch attributes from API
        $url = 'https://api.sendinblue.com/v3/contacts/attributes';
        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $form['gf-sendinblue']['key'],
            ),
        ));
        
        $response_body = json_decode($response['body'], true);
        
        $fields = [
            $list_field,
            array(
                'label'   => esc_html__( 'Key', 'gf-sendinblue' ),
                'type'    => 'text',
                'placeholder' => 'Enter Key',
                'name'    => 'key',
                'required'=>'required',
                'tooltip' => esc_html__( 'Sendinblue API Key', 'gf-sendinblue' ),
            ),
            array(
                'label'   => esc_html__( 'Email', 'gf-sendinblue' ),
                'type'    => 'select',
                'placeholder' => 'Enter Your Email',
                'name'    => 'email',
                'required'=>'required',
                'choices' => $email_fields,
                'tooltip' => esc_html__( 'Filed Type Email Only' )
            ),
        ];
        if (!empty($response_body['attributes'])) {
            foreach ($response_body['attributes'] as $attribute) {
                $name = sanitize_file_name($attribute['name']);
                $fields[] = [
                    'label'   => esc_html__( $attribute['name'], 'gf-sendinblue' ),
                    'type'    => 'select',
                    'name'    => $name,
                    'choices' => $name_fields
                ];
            }
        }

		return array(
			array(
				'title'  => esc_html__( 'Sendinblue Setting', 'gf-sendinblue' ),
				'fields' => $fields,
			),
		);
	}

	public function after_submission( $entry, $form ) {
		$data_list =  array();
        
        // Fill all the fields
        foreach ($form['fields'] as $field) {
            $value = '';
            if (!empty($entry[$field->id])) {
                $value = $entry[$field->id];
            } else if (!empty($field['inputs'])) {
                foreach ($field['inputs'] as $input) {
                    if (!empty($entry[$input['id']])) {
                        $value = $entry[$input['id']];
                    }
                }
            }
            
            if (!empty($field->adminLabel)) {
                $data_list[strtoupper($field->adminLabel)] = $value;
            }
            else if (!empty($field->label)) {
                $data_list[strtoupper($field->label)] = $value;
            }
        }
        
		$data = array(
			'email' => $entry[$form['gf-sendinblue']['email']],
		);
		if( !empty($data_list) ){
			$data['attributes'] = $data_list;
		}
		if( !empty($form['gf-sendinblue']['list']) ){
			$sendin_list_id = array( intval($form['gf-sendinblue']['list']));
			$data['listIds'] = $sendin_list_id;
		}

        /** Create Contact **/
		$url = 'https://api.sendinblue.com/v3/contacts';
		$response = wp_remote_post( $url, array(
			'body'    => json_encode($data),
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'api-key' => $form['gf-sendinblue']['key'],
			),
		));
		$resbody = json_decode($response['body'],true);
		if (isset($resbody)) {	
			if (isset($resbody['id'])) {
				$this->add_note( $entry['id'], 'Sendinblue Id - '.$resbody['id']);
			}else {
				$this->add_note( $entry['id'], 'Sendinblue Error - '.$resbody['message']);
			}
		}else{
			$this->add_note( $entry['id'], 'Mailing list subscription failed.' );
		}
	}
}
