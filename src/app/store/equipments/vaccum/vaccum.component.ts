import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-vaccum',
  templateUrl: './vaccum.component.html',
  styleUrls: ['./vaccum.component.css']
})
export class VaccumComponent implements OnInit {

  results: any = [];
  equipments=[];
  products;
  labours;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getProducts();
    this.getLabours();
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response=>{
      this.labours=response;
    });
  }
  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }

  add(data){
    this.equipments[this.equipments.length]=data.value;
    console.log(this.equipments);
    data.reset();
  }
}
