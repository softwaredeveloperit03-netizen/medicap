import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-po',
  templateUrl: './po.component.html',
  styleUrls: ['./po.component.css'],
  providers:[DatePipe]
})
export class PoComponent implements OnInit {

  pendingpo;
  selectresult = [];
  isView=false;
  selectresultproduct =[];
  client_code='';
  status='';
  from_date;
  to_date;
  clients;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPOsLog();
    this.getClients();
  }

  getPOsLog(){
    this.service.get('marketing/po.php?type=getPOsLog&client_code='+this.client_code+'&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.pendingpo =response;
    });
  }

  view(index){
    this.selectresult = this.pendingpo[index];
    this.isView = true;
  }

  viewfile(file) {
    window.open(file);
  }
  download(){
    this.service.open('marketing/po.php?type=downloadLog&from_date='+this.from_date+'&to_date='+this.to_date+'&client_code='+this.client_code);
  }
  downloadP(){
    this.service.open('marketing/po.php?type=downloadPO&order_no='+this.selectresult['order_no']);
  }
  downloadpo() {
    window.open(this.service.url + this.selectresult['file']);
  }
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients=response;
    })
  }

}
