import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawRecievingComponent } from './raw-recieving.component';

describe('RawRecievingComponent', () => {
  let component: RawRecievingComponent;
  let fixture: ComponentFixture<RawRecievingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawRecievingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RawRecievingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
