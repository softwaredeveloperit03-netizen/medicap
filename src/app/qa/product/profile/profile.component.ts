import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedProduct();
  }
  getApprovedProduct(){
    this.service.get('qa/product.php?type=getApprovedProducts').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download(id){
    this.service.open('qa/product.php?type=productmasterpdf&id='+id);
  }

  downloadReport(){
    this.service.open('qa/product.php?type=productmasterlog');
  }

}
