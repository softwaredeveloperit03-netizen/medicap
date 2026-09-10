import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DemodashComponent } from './demodash.component';

describe('DemodashComponent', () => {
  let component: DemodashComponent;
  let fixture: ComponentFixture<DemodashComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DemodashComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DemodashComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
