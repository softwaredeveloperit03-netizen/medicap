import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {
 
   isView = false;
   results = [];
   selectedSampling = [];
   loading = false;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getBatchesForGrnReceiving();
   }
  
  material_type = 'Raw Material';
  getBatchesForGrnReceiving() {
    this.loading = true;
    this.service.get('qc/sampling/raw.php?type=getBatchesForGrnReceiving&material_type=' + encodeURIComponent(this.material_type)).subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
      alertify.error('Unable to load GRN receiving batches.');
    });
  }

  viewResult(data) {
    this.selectedSampling = data;
    this.isView = true;
  }


  isCurrentDate(date: string | Date): boolean {
    const today = new Date();
    const givenDate = new Date(date);
    return today.toDateString() === givenDate.toDateString();
  }
   

  grn_receive_remark = '';
  updatesampling(status) {
 
      let temp = this.selectedSampling;
      temp['status'] = status;
      temp['grn_receive_remark'] = this.grn_receive_remark;

      this.service.post('qc/sampling/raw.php?type=updateReceivingGrn', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getBatchesForGrnReceiving();
          this.grn_receive_remark = '';
          this.isView = false;
          alertify.success("Receiving Successfully");
        }else{
          alertify.error('Some Error Occured!');
        }
      });

  }


  RejectedReceiving(status) {


    const result = confirm("Are you sure you want to reject this Sampling Intimation? Once rejected cannot retrieve again.");

    if (result) {
       
        let temp = this.selectedSampling;
        temp['status'] = status;
        temp['grn_receive_remark'] = this.grn_receive_remark;

        this.service.post('qc/sampling/raw.php?type=updateReceivingGrn', JSON.stringify(temp)).subscribe(response => {
          if (response['status'] === 'success') {
            this.getBatchesForGrnReceiving();
            this.grn_receive_remark = '';
            this.isView = false;
            alertify.success("Receiving Successfully");
          }else{
            alertify.error('Some Error Occured!');
          }
        });

    } 

  }
  


  viewCoafile(url) {
    url = this.service.url + '../../upload/coa/' + url;
    window.open(url, '_blank');
  }

    searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results || !Array.isArray(this.results)) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }







}

 
