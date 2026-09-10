import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FgreportComponent } from './fgreport.component';

describe('FgreportComponent', () => {
  let component: FgreportComponent;
  let fixture: ComponentFixture<FgreportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FgreportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FgreportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
