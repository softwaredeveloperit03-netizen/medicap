import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-prechecking',
  templateUrl: './prechecking.component.html',
  styleUrls: ['./prechecking.component.css']
})
export class PrecheckingComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  remark='';
  isrejected=false;
  isapprove=false;
  departments = [
    { name: 'Production', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Engineering/Project', status: false},
    { name: 'WareHouse', status: false},
    { name: 'EHS', status: false},
    { name: 'HR', status: false},
    { name: 'Regulatory Affairs', status: false},
    { name: 'R&D', status: false},
    { name: 'IT', status: false},
    { name: 'AQA', status: false},
    { name: 'Any Other', status: false}
  ];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC(){
    this.service.get('qms/cctemporary.php?type=getPendingPreApprovalChecking').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }

  getChanges(value){
    if(value=='REJECTED' || value=='CANCELLED'){
      this.isrejected=true;
      this.isapprove=false;
    }else{
      this.isrejected=false;
      this.isapprove=true;
    }
  }
  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp=data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['name'];
      }
    }

    temp['departments'] = test;
    this.service.post('qms/cctemporary.php?type=preApprovalChecking'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

}
