import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-glassware',
  templateUrl: './glassware.component.html',
  styleUrls: ['./glassware.component.css'],
})
export class GlasswareComponent implements OnInit {
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
