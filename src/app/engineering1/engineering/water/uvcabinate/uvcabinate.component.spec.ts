import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UvcabinateComponent } from './uvcabinate.component';

describe('UvcabinateComponent', () => {
  let component: UvcabinateComponent;
  let fixture: ComponentFixture<UvcabinateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UvcabinateComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UvcabinateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
