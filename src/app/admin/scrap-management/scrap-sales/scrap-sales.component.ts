import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-scrap-sales',
  templateUrl: './scrap-sales.component.html',
  styleUrls: ['./scrap-sales.component.css']
})
export class ScrapSalesComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  
  ngOnInit(): void {
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('qa/all2.php?type=saveScrapSales', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
