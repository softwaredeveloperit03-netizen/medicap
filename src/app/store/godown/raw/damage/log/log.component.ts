import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  vendors;
  material_type='';
  status='';
  vendor_no='';
  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDamageLog();
    this.getVendors();
  }

  getDamageLog() {
    this.service.get('store/raw.php?type=getDamageLog&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('store/raw.php?type=DamageInspectionLog&vendor_no=' + this.selectedReport['vendor_no']);
    }else{
      this.service.open('store/raw.php?type=DamageInspectionDigitalLog&vendor_no=' + this.selectedReport['vendor_no']);
    }
  }

  downloadLog(){
    this.service.open('store/raw.php?type=DamageLogPDF&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status)
  }


}
