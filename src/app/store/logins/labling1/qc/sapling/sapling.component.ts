import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sapling',
  templateUrl: './sapling.component.html',
  styleUrls: ['./sapling.component.css']
})
export class SaplingComponent implements OnInit {

  results;
  results1;
  material;
  material_name;
  from_date;
  to_date;
  loading;
    containers: string;

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
    this.service.open('pdf1/labels.php?type=samplingLabels&id='+id +'&containers='+this.results['containers']);
  }

  download(){
    this.service.open('qc/sampling.php?type=downloadSamplings');
  }
}