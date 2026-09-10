import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawlogComponent } from './rawlog.component';

describe('RawlogComponent', () => {
  let component: RawlogComponent;
  let fixture: ComponentFixture<RawlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RawlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
