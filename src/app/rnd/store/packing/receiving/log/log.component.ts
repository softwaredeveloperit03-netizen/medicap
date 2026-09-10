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
  selectedResult = [];
  vendors;
  material_type='';
  status='';
  vendor_no='';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getReceivingsLog();
    this.getVendors();
  }

  getReceivingsLog(){
    this.service.get('store/packing.php?type=getReceivingsLog&material_type='+this.material_type +'&vendor_no='+this.vendor_no +'&status='+status).subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/coa/' + link);
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('store/packing.php?type=ReceivingLog&vendor_no=' + this.selectedResult['vendor_no']);
    }else{
      this.service.open('store/packing.php?type=ReceivingDigitalLog&vendor_no=' + this.selectedResult['vendor_no']);
    }
  }


}
