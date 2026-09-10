import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  product_code='';
  vendor_no='';
  status='';
  products;
  vendors;


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworksLog();
    this.getProduct();
    this.getVendor();
  }
  getArtworksLog(){
    this.service.get('qa/artwork.php?type=getApprovedArtworks&product_code='+this.product_code+'&vendor_no='+this.vendor_no+'&status='+this.status).subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  opendoc(url){
    url=this.service.url;
    window.open(url+this.selectedResult['file']);
     //this.service.open('qa/artwork.php=getArtworksLog&id='+this.selectedResult['id']);
  }
  getProduct(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    })
  }
  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    })
  }
  openFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['design_file']);

  }

}
