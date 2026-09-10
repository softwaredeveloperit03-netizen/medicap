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
  vendor_no='';
  material_type='';
  status='';
  selectedReport = [];
  vendors;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getReceivingLog();
    this.getVendors();
  }

  getReceivingLog() {
    this.service.get('store/raw.php?type=getReceivingLog&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  downloadPDF(sign){
    this.service.open('store/raw.php?type=receivingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&vendor_no='+ this.vendor_no+'&material_type='+this.material_type+'&status='+this.status)
  }


}
