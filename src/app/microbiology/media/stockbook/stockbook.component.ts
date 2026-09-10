 import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-stockbook',
  templateUrl: './stockbook.component.html',
  styleUrls: ['./stockbook.component.css']
})
export class StockbookComponent implements OnInit {
 
  results;
  
  constructor(private service: DataAccessService ) { }

  ngOnInit(): void {
    this.getMedia();
   }
   
   isOpening = false;
  getMedia() {
    this.service.get('microbiology/media.php?type=getMediaStockBook' ).subscribe((response) => {
        this.results = response;
      });
  }


  selectedResult =[];

  isViewOpen(index){
    this.selectedResult = this.results[index];
    this.isOpening = true;
  }
 

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
     
    this.service
      .post('microbiology/media.php?type=saveMediaStock', temp)
      .subscribe((response) => {
        if (response['status'] === 'success') {
           this.getMedia();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
 
   
  
}
