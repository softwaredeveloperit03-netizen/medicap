import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PmreportComponent } from './pmreport.component';

describe('PmreportComponent', () => {
  let component: PmreportComponent;
  let fixture: ComponentFixture<PmreportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PmreportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PmreportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
