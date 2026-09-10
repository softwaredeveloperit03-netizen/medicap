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
  departments = [
    { name: 'Production', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Engineering', status: false},
    { name: 'WareHouse', status: false},
    { name: 'EHS', status: false},
    { name: 'HR', status: false},
    { name: 'Regulatory Affairs', status: false},
    { name: 'R&D', status: false},
    { name: 'IT', status: false},
    { name: 'COA', status: false},
    { name: 'Any Other', status: false}
  ];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC(){
    this.service.get('qms/cctemporary.php?type=getPendingConcernedDeptReview').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }

  save(){
    // if(!data.valid){
    //   alertify.error('All feilds are required');
    //   return;
    // }
    let temp={};
    temp['cc_no']=this.selectedResult['cc_no'];
    temp['dept_id']=this.selectedResult['dept_id'];
    temp['remark']=this.remark;
    this.service.post('qms/cctemporary.php?type=saveDeptReview'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
