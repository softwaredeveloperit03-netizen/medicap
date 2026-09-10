import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preapproval',
  templateUrl: './preapproval.component.html',
  styleUrls: ['./preapproval.component.css']
})
export class PreapprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];

  remark='';
  isrejected=false;
  isapprove=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC(){
    this.service.get('qms/cctemporary.php?type=getPendingPreApproval').subscribe(response=>{
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
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }

  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp=data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    this.service.post('qms/cctemporary.php?type=savePreApprovalCC'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
