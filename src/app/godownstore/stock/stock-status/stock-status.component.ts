import { Component, OnInit, Renderer2 } from '@angular/core';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
declare var jspdf: any;
@Component({
  selector: 'app-stock-status',
  templateUrl: './stock-status.component.html',
  styleUrls: ['./stock-status.component.css']
})
export class StockStatusComponent implements OnInit {
  stocks:any[] = [];
  status: any;
  allStocksData: any;
 
  constructor(private service: DataAccessService,private renderer: Renderer2) { }

  ngOnInit() {
     this.getAllStock();
  }

  material_type = 'Raw Material';
  
  getAllStock() {
    this.service.get('store/packing.php?type=getStock').subscribe((response: any) => {
      this.stocks = response;
      this.allStocksData = response;

     });
  }

  download(){
    this.service.open('store/packing.php?type=downloadStock');
  }

  clearFilter() {
    this.stocks = this.allStocksData;
  }

  onChangeMaterial(event: any) {
    const value = event.target.value;
  }

  onChangeStatus(event: any){
    const value = event.target.value;
    console.log('value',value);
    if(this.material_type == 'Raw Material') {
    let raw_materialData  = this.allStocksData.filter(data=> data.material_type == 'Raw Material');
    console.log('raw_materialData',raw_materialData);
    this.stocks = raw_materialData.filter(data=>data.status == value);
    console.log('this.stocks',this.stocks);
    } else if(this.material_type == 'Packing Material') {
      let packing_materialData  = this.allStocksData.filter(data=> data.material_type == 'Packing Material');
      console.log('raw_materialData',packing_materialData);
      this.stocks = packing_materialData.filter(data=>data.status == value);
      console.log('this.stocks',this.stocks);
    } else {
      alert('Please select any material type');
    }
  }
  currentDate = new Date().toLocaleDateString();

// downloadPDF() {
//   // Create a temporary container
//   const contentToConvert = this.renderer.createElement('div');

//   // Add the header HTML directly
//   const headerHTML = `
//     <header style="text-align: center; margin-bottom: 20px;">
//       <h1>Sample Data</h1>
//     </header>
//   `;
//   contentToConvert.innerHTML = headerHTML;

//   // Append the table
//   const table = document.getElementById('table');
//   if (table) {
//     contentToConvert.appendChild(table.cloneNode(true));
//   }

//   // Call the convertToPDF function passing the contentToConvert and file name after a short delay
//   setTimeout(() => {
//     this.convertToPDF(contentToConvert, 'sample');
//   }, 500); // Adjust the delay as needed

//   // Add the temporary container to the body to ensure styles are applied
//   this.renderer.appendChild(document.body, contentToConvert);
// }

// convertToPDF(contentToConvert: any, fileName: any) {
//   if (contentToConvert) {
//     html2canvas(contentToConvert).then((canvas) => {
//       const imgWidth = 208 - 10; // PDF document width in mm minus 10mm for margins
//       const pageHeight = 295 - 10; // A4 page height in mm minus 10mm for margins
//       const imgHeight = (canvas.height * imgWidth) / canvas.width; // Image height based on aspect ratio
//       const pageCanvas = document.createElement('canvas');
//       const pageCtx = pageCanvas.getContext('2d');

//       let pdf = new jsPDF('p', 'mm', 'a4'); // Create a PDF document
//       let heightLeft = canvas.height;
//       let position = 0;

//       pageCanvas.width = canvas.width;
//       pageCanvas.height = pageHeight * (canvas.width / imgWidth);

//       // Loop to add each section of the canvas to the PDF
//       while (heightLeft > 0) {
//         pageCtx.clearRect(0, 0, pageCanvas.width, pageCanvas.height);
//         pageCtx.drawImage(canvas, 0, position, pageCanvas.width, pageCanvas.height, 0, 0, pageCanvas.width, pageCanvas.height);

//         const pageDataURL = pageCanvas.toDataURL('image/png');
//         pdf.addImage(pageDataURL, 'PNG', 5, 5, imgWidth, pageHeight);

//         heightLeft -= pageCanvas.height;
//         position += pageCanvas.height;

//         if (heightLeft > 0) {
//           pdf.addPage();
//         }
//       }

//       pdf.save(`${fileName}.pdf`);
//     }).catch((error) => {
//       console.error('Error capturing element:', error);
//     });
//   } else {
//     console.error('Element not found');
//   }
// }

}
