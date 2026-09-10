import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

declare let alertify;
@Component({
  selector: 'app-queries',
  templateUrl: './queries.component.html',
  styleUrls: ['./queries.component.css'],
  providers:[DatePipe]
})
export class QueriesComponent implements OnInit {

  lists;

  from_date='';
  to_date='';
  // constructor(public service: SupportAccessService, private datePipe: DatePipe) {
  constructor(public service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getUsersLog();
  }
  
  getUsersLog(){
    this.service.get('../../../admin/api/support.php?type=GETsaveQuery&from_date' + this.from_date + '&to_date=' + this.to_date).subscribe(response=>{
      this.lists = response;
    });
  }

  viewfile(link) {
    window.open('https://aurenyxgmp.com/admin/upload/support/'+ link);
  }
  dev_assign=false;
  list_id;
  attachment;
description;
  assign_dev(id,attachment,description){
    this.dev_assign=true;
    this.list_id=id
    this.attachment=attachment
    this.description=description
  }

  updateStatus(status){
    let temp={}; 
    temp['status']=status;
    this.service.post('../../../admin/api/support.php?type=updateStatus&id='+this.list_id,JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
       
        alertify.success('SAVE');
        this.dev_assign=false;
        this.getUsersLog();
      } else{
        alertify.error(response['msg']);
      }
    });

  }
}
