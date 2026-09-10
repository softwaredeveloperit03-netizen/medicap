import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LabourMangmentComponent } from './labour-mangment.component';

describe('LabourMangmentComponent', () => {
  let component: LabourMangmentComponent;
  let fixture: ComponentFixture<LabourMangmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LabourMangmentComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LabourMangmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
