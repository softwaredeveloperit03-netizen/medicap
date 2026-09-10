import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  selectedResult=[];
  results;
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingPackingControlSamples();
  }
  getPendingPackingControlSamples(){
    this.service.get('qa/controlsample.php?type=getPendingPackingControlSamples').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  action(status){
    this.service.get('qa/controlsample.php?type=approveControlSample&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response=>{
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getPendingPackingControlSamples();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
      
    });
  }
}
