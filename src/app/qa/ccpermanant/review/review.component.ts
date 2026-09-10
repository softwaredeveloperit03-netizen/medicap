import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  remark='';
  isrejected=false;
  isapprove=false;

  reg_impact = '';
  plant_id;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
    this.plant_id = this.service.getPlantConfigFields("plant_id")

  }

  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingConcernedDeptReview').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/ccpermanant/' + link);
  }
  comment;
  change_reg_doss;
  change_pharma;
  change_update;
  change_cust_req;
  change_regulatory_req;
  dic_req;
  reg_file;
  save(){
    // if(!data.valid){
    //   alertify.error('All feilds are required');
    //   return;
    // }
    let temp={};
    temp['cc_no']=this.selectedResult['cc_no'];
    temp['dept_id']=this.selectedResult['dept_id'];
    temp['remark']=this.remark;
    temp['reg_impact'] = this.reg_impact;
    


    this.service.post('qms/ccpermanant.php?type=saveDeptReviews'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
        this.remark='';
      }else{ 
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
  selectedFile2;
  save1(data){
    // if(!data.valid){
    //   alertify.error('All feilds are required');
    //   return;
    // }
//     let temp={};
//     temp['cc_no']=this.selectedResult['cc_no'];
//     temp['dept_id']=this.selectedResult['dept_id'];
//     temp['remark']=this.remark;
//     temp['reg_impact'] = this.reg_impact;
    
// temp['comment']=this.comment;
// temp['change_reg_doss']=this.change_reg_doss;
// temp['change_pharma']=this.change_pharma;
// temp['change_update']=this.change_update;
// temp['change_cust_req']=this.change_cust_req;
// temp['change_regulatory_req']=this.change_regulatory_req;
// temp['dic_req']=this.dic_req;
// temp['reg_file']=this.reg_file;
if(!data.valid){
  alertify.error('All feilds are required');
  return;
}
const uploadData = new FormData();
if (this.selectedFile2 !== undefined) {
  uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
}
let temp = data.value;
// temp['actions']=this.attributes;
// temp['requirement']=this.requirements;
temp['remark'] = this.remark;
uploadData.append('data', JSON.stringify(temp));

    this.service.post('qms/ccpermanant.php?type=saveDeptReviews99'+'&id='+this.selectedResult['id'],uploadData).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
        this.remark='';
      }else{ 
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

}
