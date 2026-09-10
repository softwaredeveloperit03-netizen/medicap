import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { Router } from '@angular/router';


@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

  
  pendingpo;
  selectresult = [];
  isView=false;
 

  constructor(private service:DataAccessService  , private router: Router) { 
 
  }

  ngOnInit() {
    this.getPOsLog();
   }

  getPOsLog(){
    this.service.get('marketing/po.php?type=getPOsLogForReqAnalysis').subscribe(response =>{
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

   filtersFO =[];

   
   sendForReq( ) {

    this.filtersFO = this.pendingpo.filter(pending => pending.check).map(({ pid, order_no,product_code }) => ({ pid, order_no,product_code  }));
    
    console.log(this.filtersFO);

    if (this.filtersFO.length==0) {
      alert('Please Select Product!!!!!!');
      return;
    }
 
    let temp ={};
    temp['filtersFO'] = this.filtersFO;
      
    this.service.post('marketing/po.php?type=AcceptReqAnalysis', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
         this.filtersFO = [];
        alert('Send For Requirment Analysis');
        this.getPOsLog();
      } else {
        alert('An error has occurred, please try again');
      }
    });



  }


 
}
  