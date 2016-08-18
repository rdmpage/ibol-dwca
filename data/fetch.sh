#!/bin/sh

#curl http://bins.boldsystems.org/data/datarelease/NewPackages/iBOL_phase_0.50_COI.tsv.zip > iBOL_phase_0.50_COI.tsv.zip
#unzip iBOL_phase_0.50_COI.tsv.zip
#rm iBOL_phase_0.50_COI.tsv.zip
# iBOL_phase_0.50_COI.tsv is not UTF-8 encoded
iconv -f iso-8859-1 -t utf-8 iBOL_phase_0.50_COI.tsv > iBOL_phase_0.50_COI.tsv.new
rm iBOL_phase_0.50_COI.tsv
mv iBOL_phase_0.50_COI.tsv.new iBOL_phase_0.50_COI.tsv