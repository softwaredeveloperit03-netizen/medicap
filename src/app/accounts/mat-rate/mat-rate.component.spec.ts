import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MatRateComponent } from './mat-rate.component';

describe('MatRateComponent', () => {
  let component: MatRateComponent;
  let fixture: ComponentFixture<MatRateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MatRateComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MatRateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
