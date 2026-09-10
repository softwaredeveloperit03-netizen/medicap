import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EnggmaterialComponent } from './enggmaterial.component';

describe('EnggmaterialComponent', () => {
  let component: EnggmaterialComponent;
  let fixture: ComponentFixture<EnggmaterialComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EnggmaterialComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EnggmaterialComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
