import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  
  pendingpo;
  selectresult = [];
  isView=false;
 

  constructor(private service:DataAccessService  , private router: Router) { 
 
  }

  ngOnInit() {
    this.getPOsLog();
   }

  getPOsLog(){
    this.service.get('marketing/po.php?type=getCompleteReqAnalysis').subscribe(response =>{
      this.pendingpo =response;
    });
  }
  packing_materials=[];
  raw_materials=[];
  
  view(index){
    this.selectresult = this.pendingpo[index];
    this.raw_materials = this.selectresult['raw_materials'];
    this.packing_materials = this.selectresult['packing_configuration'];
    this.isView = true;
  }

  
  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
   }

     
}
  