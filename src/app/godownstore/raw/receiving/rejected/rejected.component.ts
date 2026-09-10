


import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css']
})
export class RejectedComponent implements OnInit {

  isView = false;
  isUpincedent=false;
  results;
  isupdate = false;
  selectedReport = [];
  receiveDetails=[];
  devdetails=[];
  productdetails=[];
  batches=[];
  insdetails=[];
  incidentProduct=[];
  materials;
  material_code='';
  challan_date='';
  checklist;
  
  plant_id:any;


  submitBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getInprocessReceivings();
    this.getMaterials();
   
  }

 
  getInprocessReceivings() {
    this.service.get('store/raw.php?type=getRejectedReceivings&material_code=' +this.material_code).subscribe(response => {
      this.results = response;
    });
  }
  AllRecord() {
    this.material_code = '';
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
    this.batches=this.selectedReport['batches'];
console.log('rd',this.batches);
console.log('2',this.selectedReport);
   
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


   this.getChkListData(this.selectedReport['receiving_no']);
   
    this.isView = true;
  }

  viewCoafile(url) {
    url = this.service.url + '../../../../upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
    // window.open(this.service.url+ 'upload/challan/' + this.selectedReport['challan_file']);

  }

  update(status) {
    this.submitBtnState = ClrLoadingState.LOADING;
    this.service.get('store/raw.php?type=checkReceivedMaterial&status=' + status + '&id=' + this.selectedReport['id']+ '&challan_id=' + this.selectedReport['challan_id']+'&pack_size='+this.selectedReport['pack_size']).subscribe(response => {
      if (response['status'] == 'success') {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getChkListData(rec_no) {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no='+rec_no).subscribe(response => {
      this.checklist = response;
    });
  }

}
