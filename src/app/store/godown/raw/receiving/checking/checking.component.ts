import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

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
  materials;
  material_code='';
  challan_date='';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessReceivings();
    this.getMaterials();
  }

  getInprocessReceivings() {
    this.service.get('store/raw.php?type=getInprocessReceivings&material_code=' +this.material_code).subscribe(response => {
      this.results = response;
    });
  }
  AllRecord() {
    this.service.get('store/raw.php?type=getAllInprocessReceivings').subscribe(response => {
      this.results = response;
    });
  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials=response;
    })
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
    window.open(this.service.url + 'upload/coa/' + this.selectedReport['receiving_details'].coa_file);
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
