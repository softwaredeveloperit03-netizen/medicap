import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-verify',
  templateUrl: './verify.component.html',
  styleUrls: ['./verify.component.css']
})
export class VerifyComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingQAVerification();
  }

  getPendingQAVerification(){
    this.service.get('qms/deviation.php?type=getPendingQAVerification').subscribe(response=>{
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

  save(data){
    let temp=data.value;
    temp['deviation_no']=this.selectedResult['deviation_no'];

     this.service.post('qms/deviation.php?type=verifyDeviation&id=' +this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
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
