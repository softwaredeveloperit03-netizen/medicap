import { Component, OnInit ,ElementRef, ViewChild} from '@angular/core';
 
import { DataAccessService } from 'src/app/data-access.service';
import { QrCodeService } from 'src/app/qr-code.service';
declare let alertify: any;

@Component({
  selector: 'app-stock',
  templateUrl: './stock.component.html',
  styleUrls: ['./stock.component.css'],

})
export class StockComponent implements OnInit {

  stocks;
  
  isView = false;
  isconsumption = false;
  
  selectedReport = [];
  plant_id ;
    emp_id: string;
    isDIGI: boolean=false
    isbutton: boolean=true
    materialType: any;


  constructor(private service: DataAccessService,private qrCodeService: QrCodeService) {}
 


  ngOnInit() {
     this.getgensubtype(); 
     this.getAllStock(); 
     this.plant_id = localStorage.getItem('plant_id');
   }
 


   subtypes;
   getgensubtype() {
    this.service.get('master/materialtype.php?type=get_gen_material_subtype&material_type=Microbiology Materials').subscribe((response: any) => {
      this.subtypes = response;
     });
   }


 
   material_subtype = 'Cultures';
  getAllStock() {
    this.service.get('qc/chemical.php?type=getStock&material_subtype='+this.material_subtype).subscribe(response => {
      this.stocks = response;
    });
  }


  stock_data =[];
  consumption_data =[];
  selectedJadu =[];

  view(index){
    this.stock_data =[];
    this.selectedJadu =[];
    this.stock_data = this.stocks[index].stock_data;
    
    this.isView = true;
    this.isconsumption = false;

  }

  view1(index){
    this.consumption_data =[];
    this.consumption_data = this.stocks[index].consumption_data;
    this.isView = false;
    this.isconsumption = true;

  }


  selectedFile:File;


  onFileChanged(event) {
    if(event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }


  isadddet = false;
  selectedStock =[];
  adddetails(index){
    this.isadddet = true;
    this.selectedStock = this.stock_data[index];
  }


  use_before ='';
  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.materialType=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
       if(this.materialType=='Columns')
        {
        this.save()
        }
        else
        {
          this.save1()
        }
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }


  save(){
    const uploadData = new FormData();
    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
    uploadData.append("use_before",this.use_before);
    uploadData.append("opening_date",this.opening_date);
    this.service.post('qc/chemical.php?type=saveAdditionalDetails&material_code='+
    this.selectedStock['material_code']+'&ser_no='+this.selectedStock['batch_no'] +'&id='+this.selectedStock['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isbutton=true
        this.isadddet = false;
        this.getAllStock(); 
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  opening_date;
  save1(){
    const uploadData = new FormData();
    
    uploadData.append("use_before",this.use_before);
    uploadData.append("opening_date",this.opening_date);
    this.service.post('qc/chemical.php?type=saveAdditionalDetails1&id='+this.selectedStock['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isadddet = false;
        this.getAllStock(); 
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 
  qrCodeImage: string | undefined; 

  isQR=false;
  generate(material_code,index) {
    // Generate the QR code

    this.selectedJadu = this.stocks[index];
    console.log(material_code);
    
    const url = 'https://aurenyxgmp.com/php/phpdevlop/gmptotal1/store/raw.php?type=stock_details_other&material_code='+material_code +'&plant_id='+this.plant_id+ '&token=' + localStorage.getItem('token');
    this.qrCodeImage = this.qrCodeService.generateQRCode(url);

    // Open the modal
    this.isQR = true;
  }



  no_of_qr =0;

  @ViewChild('qrCodeImage', {static: false}) qrCodeImageElement: ElementRef;

  print_qr() {
    
    const printContents = document.getElementById('qrCodeImage')?.outerHTML;
    const originalContents = document.body.innerHTML;
     const printWindow = window.open('', '_blank', 'width=600,height=600');
    printWindow?.document.open();
    printWindow?.document.write(printContents!);
    printWindow?.document.close();

     printWindow?.addEventListener('load', () => {
      printWindow?.focus();
      printWindow?.print();
      printWindow?.close();
    });
     document.body.innerHTML = originalContents;
     this.isQR = false;
  }

}
