import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from "@angular/common";
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  vendors;
  material_type='';
  status='';
  vendor_no='';
  from_date='';
  today='';
  to_date='';
  challan_for='';
  vendor_name='';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }  

  ngOnInit(): void {
    this.getReceivingsLog();
    this.getVendors();
  }

  getReceivingsLog() {
    this.service.get('store/packing.php?type=getReceivingsLog&vendor_name='+this.vendor_name+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
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
  download()
  {
    this.service.open('store/packing.php?type=downloadReceivingsLog&vendor_no='+this.vendor_no+'&from_date='+this.from_date+'&to_date='+this.to_date);
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

  AllRecord(){
    this.service.get('store/packing.php?type=getAllReceivingsLog').subscribe((response : any) => {
      this.results = response;
    });
    this.vendor_name='';
  }

}
