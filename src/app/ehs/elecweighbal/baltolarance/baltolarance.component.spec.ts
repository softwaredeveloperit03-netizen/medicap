import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BaltolaranceComponent } from './baltolarance.component';

describe('BaltolaranceComponent', () => {
  let component: BaltolaranceComponent;
  let fixture: ComponentFixture<BaltolaranceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BaltolaranceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BaltolaranceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
