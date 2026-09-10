import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-chemicals',
  templateUrl: './chemicals.component.html',
  styleUrls: ['./chemicals.component.css'],
})
export class ChemicalsComponent implements OnInit {
  loading;
  results = [];
  stocks: Object;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
       this.getAllStock();
  }

  material_subtype = 'Chemicals';
  getAllStock() {
    this.service
      .get(
        'qc/chemical.php?type=getStock&material_subtype=' +
          this.material_subtype
      )
      .subscribe((response) => {
        this.stocks = response;
      });
  }
}
