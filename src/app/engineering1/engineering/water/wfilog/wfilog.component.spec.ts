import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WfilogComponent } from './wfilog.component';

describe('WfilogComponent', () => {
  let component: WfilogComponent;
  let fixture: ComponentFixture<WfilogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WfilogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WfilogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
