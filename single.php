<?php

 // --------------------------------------------------
 //
 // single
 //
 // --------------------------------------------------

	if(have_posts()){
		while(have_posts()){
			the_post();
			$ldc_vault = new LDC_Vault(get_the_ID());
			$ldc_vault->enqueue_styles_and_scripts();
			$ldc_vault->get_header();
			$html = $ldc_vault->render_data_table();
			echo $html;
			$ldc_vault->get_footer();
		}
	}

 // --------------------------------------------------
