import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SaplingComponent } from './sapling.component';

describe('SaplingComponent', () => {
  let component: SaplingComponent;
  let fixture: ComponentFixture<SaplingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SaplingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SaplingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
