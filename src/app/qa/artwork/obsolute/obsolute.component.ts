import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-obsolute',
  templateUrl: './obsolute.component.html',
  styleUrls: ['./obsolute.component.css']
})
export class ObsoluteComponent implements OnInit {
  results;
  isView = false;
  selectedResult=[];
  product_code='';
  vendor_no='';
  status='';
  products;
  vendors;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getObsoluteArtworks();
    this.getProduct();
    this.getVendor();
  }
  getObsoluteArtworks(){
    this.service.get('qa/artwork.php?type=getObsoluteArtworks&product_code='+this.product_code+'&vendor_no='+this.vendor_no+'&status='+this.status).subscribe(response=>{
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

}
