import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-testing',
  templateUrl: './testing.component.html',
  styleUrls: ['./testing.component.css']
})
export class TestingComponent implements OnInit {

  results;
  results1;
  material;
  material_name;
  from_date;
  to_date;
  loading;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getReceivingLog();
    this.getMaterials();

  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.material = response;
    });
  }

  getReceivingLog() {
    this.service.get('qc/sampling.php?type=getSamplings').subscribe(response => {
      this.results = response;
      this.results1 = response;
    });
  }
  printLabel(id){
    this.service.open('pdf1/labels.php?type=testingLabels&id='+id);
  }

  download(){
    this.service.open('qc/testing.php?type=downloadtesting');
  }

  filterItem(){
    this.results=[];
    for(let i=0; i<this.results1.length; i++){
      let material = this.results1[i];
      console.log(material);
      // console.log(material.material_name, this.material_name);
      if(material.material_name!=null){
        let from = new Date(this.from_date);
        from.setDate(from.getDate()-1);
        let to = new Date(this.to_date);
        to.setDate(to.getDate()+1);
        let now = new Date(material['grn_date']);
        // console.log(from, now, to);
          if(material.material_name.toUpperCase().includes(this.material_name.toUpperCase()) && (from<now && now<to)){
            this.results.push(material);
          }
        
      }
    }
  }

  // AllRecord(){
  //   this.material_name='';
  //   this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
  //   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
  //   this.results=this.results1;
  // }
}