import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-hold',
  templateUrl: './hold.component.html',
  styleUrls: ['./hold.component.css'],
  providers:[DatePipe]
})
export class HoldComponent implements OnInit {

  isView = false;
  results;
  vendors;
  selectedResult = [];
  material_type='';
  from_date='';
  to_date='';
  today='';
  vendor_no='';
  reason='';
  status='approve';
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getHoldChallan();
    this.getVendors();
  }

  getHoldChallan(){
    this.service.get('store/challan.php?type=getHoldChallan&material_type=' + this.material_type+'&from_date=' + this.from_date+'&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  updateChallan(status) {
    this.selectedResult['reason'] = this.reason;
    this.service.post('store/challan.php?type=updateChallan&status=' + status + '&id=' + this.selectedResult['id'] + '&po_no=' + this.selectedResult['po_no'] , JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        this.reason = '';
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getHoldChallan();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  downloadLog(){
    this.service.open('pdf1/store.php?type=challanLog')
  }
  download(){
    this.service.open('store/challan.php?type=downloadChallanLog&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date +'&status='+status)
  }

  upload(){
    window.open(this.service.url+'upload/challan/'+this.selectedResult['challan_file']);
  }

  AllRecord(){
    this.service.get('store/challan.php?type=getAllHoldChallan').subscribe((response : any) => {
      this.results = response;
    });
    this.material_type='';

  }

}
