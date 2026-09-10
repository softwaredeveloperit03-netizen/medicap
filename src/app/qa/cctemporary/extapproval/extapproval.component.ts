import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-extapproval',
  templateUrl: './extapproval.component.html',
  styleUrls: ['./extapproval.component.css']
})
export class ExtapprovalComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC(){
    this.service.get('qms/cctemporary.php?type=getPendingExtensionChecking').subscribe(response=>{
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

  checkExtension(status){
    this.service.get('qms/cctemporary.php?type=checkExtension&id='+this.selectedResult['id'] +'&status='+status +'&cc_no='+this.selectedResult['cc_no']).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Extension Approve successfuly');
        this.isView=false;
        this.getInitiatedCC();
      }else{
        alertify.error('Some error Occured');
      }
    });
  }
  
}
