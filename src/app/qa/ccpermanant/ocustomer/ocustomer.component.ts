import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-ocustomer',
  templateUrl: './ocustomer.component.html',
  styleUrls: ['./ocustomer.component.css']
})
export class OcustomerComponent implements OnInit {
  
  results;
  isView=false;
  selectedResult=[];
  preapproval_customer='';
  remark='';
  isrejected=false;
  isapprove=false;
  constructor(private service:DataAccessService) { }

  departments = [
    { name: 'Production', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Engineering/Project', status: false},
    { name: 'WareHouse', status: false},
    { name: 'EHS', status: false},
    { name: 'HR', status: false},
    { name: 'Regulatory Affairs', status: false},
    { name: 'R&D', status: false},
    { name: 'Purchase', status: false},
    { name: 'Marketing', status: false},
    { name: 'IT', status: false},
    { name: 'AQA', status: false},
    { name: 'Any Other', status: false}
  ];
  ngOnInit(): void {
    this.getInitiatedCC();
  }
 updateDept(value, i) {
    this.departments[i].status = value;
  }
  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingPreApprovalOcustomer').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
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
  viewfile(link) {
    window.open(this.service.url + 'upload/ccpermanant/' + link);
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
    this.service.post('qms/ccpermanant.php?type=savePreApprovalCustomer1'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
