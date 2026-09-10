import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedReport=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSpillages();
  }
  getCheckedSpillages(){
    this.service.get('store/spillage.php?type=getCheckedRawSpillages').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
  
  approveSpillage(status){
    this.service.get('store/spillage.php?type=approveSpillage&status=' + status + '&id=' + this.selectedReport['id'] ).subscribe(response => {
      if (response['status']=='success'){
        alertify.success('Spillage Material Approved Successfuly');
        this.isView = false;
        this.getCheckedSpillages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  

}
