import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-reject',
  templateUrl: './reject.component.html',
  styleUrls: ['./reject.component.css']
})
export class RejectComponent implements OnInit {
  isView = false;
  isUpincedent=false;
  results;
  isupdate = false;
  selectedReport = [];
  receiveDetails=[];
  devdetails=[];
  productdetails=[];
  insdetails=[];
  incidentProduct=[];
  constructor(private service: DataAccessService) { }
  ngOnInit(): void {
    this.getInprocessReceivings();
  }
  getInprocessReceivings() {
    this.service.get('store/raw.php?type=getRejectedReceving').subscribe(response => {
      this.results = response;
    });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.receiveDetails=this.selectedReport['receiving_details'];

  
    if(this.selectedReport['error_type']=='error2'){
      this.devdetails=this.selectedReport['deviations'];
      this.productdetails=this.devdetails['product_details'];
      console.log(this.productdetails);
    }else if(this.selectedReport['error_type']=='error1'){
      this.insdetails=this.selectedReport['incidents'];
     this.incidentProduct=this.insdetails['product_details'];
     console.log('tt',this.incidentProduct);
   }
   
    this.isView = true;
  }
  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }
  update(status) {
    this.service.get('store/raw.php?type=checkReceivedMaterial&status=' + status + '&id=' + this.selectedReport['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
