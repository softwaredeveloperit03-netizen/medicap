import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { Subject } from 'rxjs';
import { debounceTime } from 'rxjs/operators';
@Component({
  selector: 'app-yealyforecast',
  templateUrl: './yealyforecast.component.html',
  styleUrls: ['./yealyforecast.component.css']
})
export class YealyforecastComponent implements OnInit {


  results;
  selectresult = [];
  isView=false;


  constructor(private service:DataAccessService  , private router: Router) {

  }
      
  currentMonthIndex: number;
  months: string[] = [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
  ];

  // Subject for debouncing month updates
  private updateSubject = new Subject<{ item: any, month: string }>();

  

  ngOnInit() {
    this.getPOsLog();

    const today = new Date();
    this.currentMonthIndex = today.getMonth(); // 0 = Jan, 11 = Dec

    // Debounce API calls (4 sec)
    this.updateSubject.pipe(debounceTime(2000)).subscribe(payload => {
      this.sendMonthUpdate(payload.item, payload.month);
    });
  }

  getPOsLog() {
    this.service.get('mrp/mrp.php?type=Get_Qty_For_YearlyForcast').subscribe((response: any[]) => {   
      this.results = response;

      // Initialize monthly quantities if null
      this.results.forEach(item => {
        this.months.forEach(m => {
          if (item[m.toLowerCase()] === null) item[m.toLowerCase()] = 0;
        });
        this.updateRemaining(item);
      });
    });
  }

  updateRemaining(item: any) {
    let sum = 0;
    this.months.forEach(m => {
      sum += Number(item[m.toLowerCase()] || 0);
    });
    item.remainingQty = item.order_qty - sum;
  }

  updateMonthQty(item: any, monthColumn: string) {
    this.updateRemaining(item);

    // Push into subject for debounced API call
    this.updateSubject.next({ item, month: monthColumn });
  }

  private sendMonthUpdate(item: any, monthColumn: string) {
    const payload = {
      id: item.id,
      yfq_common_id: item.yfq_common_id,
      month: monthColumn,
      value: item[monthColumn],
      remainingQty: item.remainingQty
    };

    this.service.post('mrp/mrp.php?type=update_monthly_forecast', payload)
      .subscribe(response => {
        if (response['status'] === 'success') {
          console.log(`Updated ${monthColumn} for id ${item.id}`);
        } else {
          console.error('Update failed:', response['error']);
        }
      });
  }
}
