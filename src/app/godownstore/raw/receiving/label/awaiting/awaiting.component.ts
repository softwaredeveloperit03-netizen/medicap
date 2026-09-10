import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  material_name = '';
  results;
  results1;
  materials;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAwaitingReceivingRawLabels();
    this.getMaterials();
  }

  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }

  getAwaitingReceivingRawLabels() {
    this.service.get('store/label.php?type=getAwaitingReceivingRawLabels').subscribe(response => {
      this.results = response;
      this.results1= response;
    });
  }

  print(status,data) {
    this.service.open('store/label.php?type=printReceivingLabel&id=' + data['id'] + '&batch_no=' + data['batch_no']+'&print_status='+status);
  }

  filterMaterial() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let material = this.results1[i];
        if (material['material_name'].toUpperCase().includes(this.material_name.toUpperCase()) ) {
          this.results[this.results.length] = material;
        }
    }
  }

  clear(){
    this.material_name = '';
    this.results = this.results1;
  }

}
