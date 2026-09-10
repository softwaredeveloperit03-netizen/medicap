import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinalOqreportComponent } from './final-oqreport.component';

describe('FinalOqreportComponent', () => {
  let component: FinalOqreportComponent;
  let fixture: ComponentFixture<FinalOqreportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinalOqreportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinalOqreportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
