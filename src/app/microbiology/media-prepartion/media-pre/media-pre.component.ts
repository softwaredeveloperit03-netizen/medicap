import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-media-pre',
  templateUrl: './media-pre.component.html',
  styleUrls: ['./media-pre.component.css'],
  providers:[DatePipe]
})
export class MediaPreComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  lafs;
  balances;

  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.getMedia();
  }
  
 
  getMedia(){
    this.service.get('microbiology/media.php?type=getMediaPrepartion&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/media.php?type=downloadMediaPrepartion&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/media.php?type=saveMediaPrepartion',JSON.stringify (data.value)).subscribe(response =>{
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
