import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawGrnComponent } from './raw-grn.component';

describe('RawGrnComponent', () => {
  let component: RawGrnComponent;
  let fixture: ComponentFixture<RawGrnComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawGrnComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RawGrnComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
