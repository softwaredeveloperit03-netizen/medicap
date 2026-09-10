import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.css']
})
export class ViewComponent implements OnInit {

  bulkStockId: number;
  bulkStockDetails: any = {};
  usesList:any = [];
  loading = false;

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.bulkStockId = +this.route.snapshot.paramMap.get('id');
    this.getBulkStockDetails();
    this.getBulkStockUses();
  }

  getBulkStockDetails() {
    this.service.get('production/bulk_stock.php?type=getBulkStockDetails&id=' + this.bulkStockId).subscribe(response => {
      this.bulkStockDetails = response || {};
    }, error => {
      console.error('Error fetching bulk stock details:', error);
    });
  }

  getBulkStockUses() {
    this.loading = true;
    this.service.get('production/bulk_stock.php?type=getBulkStockUses&id=' + this.bulkStockId).subscribe(response => {
      this.usesList = response || [];
      this.loading = false;
    }, error => {
      this.loading = false;
      console.error('Error fetching bulk stock uses:', error);
    });
  }

  close() {
    this.router.navigate(['/fproduction/bulk_stock']);
  }

}





