import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HttpClient } from '@angular/common/http';
declare let alertify;

@Component({
  selector: 'app-foapproval',
  templateUrl: './foapproval.component.html',
  styleUrls: ['./foapproval.component.css']
})
export class FoapprovalComponent implements OnInit {
  clients;
  client_code;
  po_date;
  pendingpo;
  selectresult =[];
  isView= false;
  usdExchangeRate: number;
    emp_id: string;
    isDIGI=false
    status: any;
  constructor(private service:DataAccessService, private http: HttpClient) { }

  ngOnInit() {
    this.getPendingPOs();
    this. getClients();

    this.http
      .get('https://open.er-api.com/v6/latest/USD')
      .subscribe(
        (data: any) => {
          this.usdExchangeRate = data.rates.INR;
        },
        (error) => {
          console.error('Error fetching USD exchange rate:', error);
          this.usdExchangeRate = 1; // Default to 1 if there's an error
        }
      );
  }

getPendingPOs(){
  this.service.get('marketing/po.php?type=getPendingPOs&client_code='+this.client_code + '&po_date='+this.po_date ).subscribe(response =>{
    this.pendingpo =response;
  });
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients=response;
    });
  }
  rate;
  view(index){
    this.selectresult = this.pendingpo[index];
   
    this.isView = true;
  }
  selectedProduct =[];
  isProduct = false;
  pack_size = '';
  pack_size_unit = '';
 
  view1(index){

    this.pack_size = '';
    this.pack_size_unit = '';

    this.selectedProduct = this.selectresult['products'][index].details;
 
    const pksize = this.selectedProduct['pack_size'];
    console.log(pksize);

    this.pack_size = pksize.pack_size;
    this.pack_size_unit = pksize.unit;

    console.log(this.pack_size +" "+this.pack_size_unit);
    
    this.isProduct = true;
  }

  remark;

  updatePendingPOs(status) {

    this.service.get('marketing/po.php?type=updatePendingPOs&status=' + status + '&id=' + this.selectresult['id']+'&remark='+this.remark).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
           alert('Update Successfully');
           this.isView = false;
           this.getPendingPOs();
       } else {
        alert('An error has occurred, please try again');
      }

    });
  }


  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
  }

 
  

  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
   }


 
   
}


