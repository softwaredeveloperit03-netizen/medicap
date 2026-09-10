import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-unit-formula',
  templateUrl: './unit-formula.component.html',
  styleUrls: ['./unit-formula.component.css']
})
export class UnitFormulaComponent implements OnInit {
  isView = false;
  results;

  selectedResult = [];

  dosage_form = '';
  product_code = '';
  status = '';

  dosages;
  products;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getUnitFormulas();
    this.getDosages();
  }

  getUnitFormulas(){
    this.service.get('production/bom.php?type=getUnitFormulas&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts() {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true; 
  }

  downloadbom(id) {
    this.service.open('production/bom.php?type=downloadBOM&id=' + id);
  }

}
