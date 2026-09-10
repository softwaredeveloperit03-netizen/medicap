import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-consumption',
  templateUrl: './consumption.component.html',
  styleUrls: ['./consumption.component.css'],
  providers: [DatePipe]
})
export class ConsumptionComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  labours;
  lafs;
  balances;
  medias=[];

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMedia();

  }


  getMedia() {
    this.medias = [];
    this.service.get('microbiology/media.php?type=getMediaConsumption&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
      for (let x = 0; x < this.results.length; x++) {
          let obj = this.results[x];
          obj['prepare_on']='';
          obj['plate_no']='';
          obj['use_before']='';
          obj['used_on']='';
          obj['used_for']='';
          obj['remaining_qty']='';
          obj['used_by']='';
          this.medias.push(obj);
      }
    });
  }

  download() {
    this.service.open('microbiology/media.php?type=downloadMediaConsumption&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  save(data) {
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    this.service.post('microbiology/media.php?type=saveMediaConsumption', JSON.stringify(data)).subscribe(response => {
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
