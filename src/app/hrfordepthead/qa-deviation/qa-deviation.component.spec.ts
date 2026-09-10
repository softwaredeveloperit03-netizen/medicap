import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QaDeviationComponent } from './qa-deviation.component';

describe('QaDeviationComponent', () => {
  let component: QaDeviationComponent;
  let fixture: ComponentFixture<QaDeviationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QaDeviationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QaDeviationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
