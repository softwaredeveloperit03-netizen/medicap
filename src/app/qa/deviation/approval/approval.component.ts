import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  isrejected=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingQAVerification();
  }

  getPendingQAVerification(){
    this.service.get('qms/deviation.php?type=getPendingQAApproval').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + '../../upload/deviation/' + link);
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  getAssurance(value){
    if(value=='REJECTED'){
      this.isrejected=true;
    }else{
      this.isrejected=false;
    }
  }

  save(data){
    let temp=data.value;
    temp['deviation_no']=this.selectedResult['deviation_no'];

     this.service.post('qms/deviation.php?type=approveDeviation&id=' +this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getPendingQAVerification();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }



}
