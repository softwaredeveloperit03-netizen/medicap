import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SoplogComponent } from './soplog.component';

describe('SoplogComponent', () => {
  let component: SoplogComponent;
  let fixture: ComponentFixture<SoplogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SoplogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SoplogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
