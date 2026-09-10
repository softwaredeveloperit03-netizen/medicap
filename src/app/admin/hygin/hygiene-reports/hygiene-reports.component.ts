import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-hygiene-reports',
  templateUrl: './hygiene-reports.component.html',
  styleUrls: ['./hygiene-reports.component.css']
})
export class HygieneReportsComponent implements OnInit {


  cleaning_report;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.get_dust_bin_data();
    this.get_factory_clean_data();
    this.get_toilet_clean_data();
    this.get_etp_log_data();
    this.get_ro_log_data();
    this.get_scrap_log_data();
    this.get_personal_hygiene_report();
    this.westDispdata1();
    
   }


  dust_bin_data;
  get_dust_bin_data() {
    this.service.get('admin/housekeeping.php?type=get_dust_bin_data').subscribe(response => {
      this.dust_bin_data = response;
    });
  }
  westDispdata;
  westDispdata1() {
    this.service.get('admin/housekeeping.php?type=west_disp_data').subscribe(response => {
      this.westDispdata = response;
    });
  }
  allValuesBlank(result: any): boolean {
    return !result.firstname && !result.middlename && !result.lastname;
  }
  

  factory_clean_data;
  get_factory_clean_data() {
    this.service.get('admin/housekeeping.php?type=get_factory_clean_data').subscribe(response => {
      this.factory_clean_data = response;
    });
  }


  toilet_clean_data;
  get_toilet_clean_data() {
    this.service.get('admin/housekeeping.php?type=get_toilet_clean_data').subscribe(response => {
      this.toilet_clean_data = response;
    });
  }

  etp_log_data;
  get_etp_log_data() {
    this.service.get('admin/housekeeping.php?type=get_etp_log_data').subscribe(response => {
      this.etp_log_data = response;
    });
  }


  ro_log_data;
  get_ro_log_data() {
    this.service.get('admin/housekeeping.php?type=get_ro_log_data').subscribe(response => {
      this.ro_log_data = response;
    });
  }

  scrap_log_data;
  get_scrap_log_data() {
    this.service.get('admin/housekeeping.php?type=get_scrap_data').subscribe(response => {
      this.scrap_log_data = response;
    });
  }

  
  pest_control_data;
  get_pest_control_data(val) {
    this.service.get('admin/housekeeping.php?type=get_pest_control_data&service_type='+val).subscribe(response => {
      this.pest_control_data = response;
    });
  }

  personal_hygiene_report;
  get_personal_hygiene_report( ) {
    this.service.get('admin/housekeeping.php?type=personal_hygiene_report').subscribe(response => {
      this.personal_hygiene_report = response;
    });
  }


  download() {
    this.service.open('admin/housekeeping.php?type=download_personal_hygiene&report='+this.cleaning_report)
  }

  dustview;
  dust = false;
  dust_bin_check_data;
  view(index){
    this.dust = true;
    this.dustview = this.dust_bin_data[index];
    this.dust_bin_check_data   = JSON.parse(this.dustview['dust_bin_check_data']);
    console.log(this.dustview);
  }



}
